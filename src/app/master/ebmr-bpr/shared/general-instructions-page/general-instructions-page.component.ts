import { Component, EventEmitter, HostBinding, Input, Output } from '@angular/core';
import { DomSanitizer, SafeHtml } from '@angular/platform-browser';
import {
  ProcedureParagraph,
  assignProcedureNumbers,
  blankProcedureParagraph,
} from '../procedure.util';
import { GeneralInstructionsContent, syncGeneralInstructionsText } from '../general-instructions.util';

@Component({
  selector: 'app-general-instructions-page',
  templateUrl: './general-instructions-page.component.html',
  styleUrls: ['../safety-precautions/safety-precautions.component.css'],
})
export class GeneralInstructionsPageComponent {
  @Input() content: GeneralInstructionsContent = { paragraphs: [] };
  @Input() editable = true;
  @Input() showSave = false;
  @Input() saving = false;
  @Input() expanded = false;

  @HostBinding('class.sp-expanded') get expandedHost(): boolean {
    return this.expanded;
  }
  @Output() contentChange = new EventEmitter<GeneralInstructionsContent>();
  @Output() save = new EventEmitter<void>();

  levelOptions = [
    { value: 1, label: '1.0 — Main point' },
    { value: 2, label: '1.1.0 — Sub-point' },
    { value: 3, label: '1.1.1.0 — Detail' },
  ];

  constructor(private sanitizer: DomSanitizer) {}

  get paragraphs(): ProcedureParagraph[] {
    return this.content?.paragraphs || [];
  }

  get numbered(): ReturnType<typeof assignProcedureNumbers> {
    return assignProcedureNumbers(this.paragraphs);
  }

  get previewHtml(): SafeHtml {
    const parts = this.numbered
      .filter((p) => (p.text || '').trim())
      .map(
        (p) =>
          `<div class="sp-prev-row sp-lv-${p.level}"><span class="sp-prev-num">${p.number}</span><span class="sp-prev-text">${this.escapeHtml(p.text)}</span></div>`
      );
    return this.sanitizer.bypassSecurityTrustHtml(parts.join(''));
  }

  private escapeHtml(s: string): string {
    return String(s || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;');
  }

  emit(): void {
    syncGeneralInstructionsText(this.content);
    this.contentChange.emit(this.content);
  }

  addPoint(level = 1): void {
    if (!this.content.paragraphs) this.content.paragraphs = [];
    this.content.paragraphs.push(blankProcedureParagraph(level));
    this.emit();
  }

  removePoint(i: number): void {
    this.content.paragraphs.splice(i, 1);
    if (!this.content.paragraphs.length) {
      this.content.paragraphs.push(blankProcedureParagraph(1));
    }
    this.emit();
  }

  movePoint(i: number, dir: number): void {
    const j = i + dir;
    if (j < 0 || j >= this.content.paragraphs.length) return;
    const arr = this.content.paragraphs;
    [arr[i], arr[j]] = [arr[j], arr[i]];
    this.emit();
  }

  onSave(): void {
    syncGeneralInstructionsText(this.content);
    this.save.emit();
  }
}
