(function () {
  const SCRIPT_URL = 'https://cdn.jsdelivr.net/npm/tesseract.js@5.1.1/dist/worker.min.js';
  const CORE_URL = 'https://cdn.jsdelivr.net/npm/tesseract.js-core@5.1.1';
  const LANGUAGE_URL = 'https://tessdata.projectnaptha.com/4.0.0';
  let workerPromise;

  function cleanLine(line) {
    return String(line || '').replace(/\s+/g, ' ').trim();
  }

  function labeledValue(lines, expression, labels) {
    for (let index = 0; index < lines.length; index += 1) {
      const match = lines[index].match(expression);
      if (!match) continue;
      const inline = cleanLine(match[1]);
      if (inline && !labels.test(inline)) return inline;
      for (let next = index + 1; next < Math.min(lines.length, index + 3); next += 1) {
        const candidate = cleanLine(lines[next]);
        if (candidate && !labels.test(candidate)) return candidate;
      }
    }
    return '';
  }

  function findDate(lines) {
    const birthLabel = /date\s+of\s+birth|birth\s+date|birthday|kapanganakan|petsa\s+ng\s+kapanganakan/i;
    const numericDate = /\b((?:19|20)\d{2})[-/.](0?[1-9]|1[0-2])[-/.](0?[1-9]|[12]\d|3[01])\b|\b(0?[1-9]|1[0-2])[-/.](0?[1-9]|[12]\d|3[01])[-/.]((?:19|20)\d{2})\b/;
    const monthNames = 'JAN(?:UARY)?|FEB(?:RUARY)?|MAR(?:CH)?|APR(?:IL)?|MAY|JUN(?:E)?|JUL(?:Y)?|AUG(?:UST)?|SEP(?:TEMBER)?|OCT(?:OBER)?|NOV(?:EMBER)?|DEC(?:EMBER)?';
    const namedDate = new RegExp('\\b(' + monthNames + ')\\s+(\\d{1,2}),?\\s+((?:19|20)\\d{2})\\b|\\b(\\d{1,2})\\s+(' + monthNames + ')\\s+((?:19|20)\\d{2})\\b', 'i');
    const candidates = [];
    lines.forEach(function (line, index) {
      if (birthLabel.test(line)) {
        candidates.push(line);
        if (lines[index + 1]) candidates.push(lines[index + 1]);
      }
    });
    for (const line of candidates) {
      let match = line.match(numericDate);
      if (match) {
        if (match[1]) return match[1] + '-' + match[2].padStart(2, '0') + '-' + match[3].padStart(2, '0');
        return match[6] + '-' + match[4].padStart(2, '0') + '-' + match[5].padStart(2, '0');
      }
      match = line.match(namedDate);
      if (match) {
        const dateText = match[0];
        const parsed = new Date(dateText);
        if (!Number.isNaN(parsed.getTime())) {
          return parsed.getFullYear() + '-' + String(parsed.getMonth() + 1).padStart(2, '0') + '-' + String(parsed.getDate()).padStart(2, '0');
        }
      }
    }
    return '';
  }

  function extractDetails(text) {
    const lines = String(text || '')
      .split(/\r?\n/)
      .map(cleanLine)
      .filter(Boolean);
    const labels = /^(?:philippine identification|national id|republic of the philippines|date of birth|birth date|birthday|sex|kasarian|address|tirahan|apelyido|last name|surname|given name|first name|middle name|pangalan)\b/i;
    let fullName = labeledValue(
      lines,
      /^(?:FULL\s*NAME|PANGALAN\s*\/?\s*NAME|NAME)\s*[:\-]?\s*(.*)$/i,
      labels
    );
    if (!fullName) {
      const first = labeledValue(lines, /^(?:GIVEN\s*NAME|FIRST\s*NAME|PANGALAN)\s*[:\-]?\s*(.*)$/i, labels);
      const middle = labeledValue(lines, /^(?:MIDDLE\s*NAME|GITNANG\s*APELYIDO)\s*[:\-]?\s*(.*)$/i, labels);
      const last = labeledValue(lines, /^(?:LAST\s*NAME|SURNAME|APELYIDO)\s*[:\-]?\s*(.*)$/i, labels);
      fullName = [first, middle, last].filter(Boolean).join(' ');
    }
    fullName = fullName
      .replace(/^(?:full\s*name|name|pangalan)\s*[:\-]?\s*/i, '')
      .replace(/[|]+/g, ' ')
      .replace(/\s+/g, ' ')
      .trim();

    let sex = labeledValue(lines, /^(?:SEX|GENDER|KASARIAN)\s*[:\-]?\s*(.*)$/i, labels);
    if (/^(?:f|female|babae)\b/i.test(sex)) sex = 'Female';
    else if (/^(?:m|male|lalaki)\b/i.test(sex)) sex = 'Male';
    else sex = '';

    const address = labeledValue(lines, /^(?:ADDRESS|TIRAHAN|RESIDENTIAL\s+ADDRESS)\s*[:\-]?\s*(.*)$/i, labels)
      .replace(/^(?:address|tirahan)\s*[:\-]?\s*/i, '')
      .slice(0, 300);

    return {
      full_name: fullName.slice(0, 100),
      date_of_birth: findDate(lines),
      sex: sex,
      address: address
    };
  }

  async function getWorker(onProgress) {
    if (typeof Tesseract === 'undefined') {
      throw new Error('ID text reader did not load. Check your internet connection and reload.');
    }
    if (!workerPromise) {
      workerPromise = Tesseract.createWorker('eng', 1, {
        workerPath: SCRIPT_URL,
        corePath: CORE_URL,
        langPath: LANGUAGE_URL,
        gzip: true,
        logger: function (message) {
          if (onProgress && message.status === 'recognizing text') {
            onProgress(Math.round(message.progress * 100));
          }
        }
      }).catch(function (error) {
        workerPromise = null;
        throw new Error('ID text reader could not start. Check your internet connection and try again.');
      });
    }
    return workerPromise;
  }

  async function readId(file, onProgress) {
    const worker = await getWorker(onProgress);
    const result = await worker.recognize(file);
    return extractDetails(result.data.text);
  }

  window.IdReader = { readId: readId };
}());
