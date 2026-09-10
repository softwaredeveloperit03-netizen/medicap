import { Component, ElementRef, forwardRef, Input, ViewChild } from '@angular/core';
import { ControlValueAccessor, NG_VALUE_ACCESSOR } from '@angular/forms';
import { QuillEditorComponent } from 'ngx-quill';
import {
  insertHtmlAtCursor,
  insertInProcessBlockHtml,
  insertTableHtml,
  PROCEDURE_QUILL_MODULES,
} from '../procedure-editor.config';

declare let alertify: any;

@Component({
  selector: 'app-procedure-draft-editor',
  templateUrl: './procedure-draft-editor.component.html',
  styleUrls: ['./procedure-draft-editor.component.css'],
  providers: [
    {
      provide: NG_VALUE_ACCESSOR,
      useExisting: forwardRef(() => ProcedureDraftEditorComponent),
      multi: true,
    },
  ],
})
export class ProcedureDraftEditorComponent implements ControlValueAccessor {
  @Input() placeholder = 'Draft procedure content…';
  @Input() minHeight = '120px';
  @ViewChild('quill') quillRef?: QuillEditorComponent;
  @ViewChild('imageInput') imageInput?: ElementRef<HTMLInputElement>;

  editorModules = PROCEDURE_QUILL_MODULES;
  content = '';
  disabled = false;
  tablePromptOpen = false;
  tableRows = 3;
  tableCols = 3;

  private onChange: (v: string) => void = () => {};
  private onTouched: () => void = () => {};

  writeValue(value: string): void {
    this.content = value || '';
  }

  registerOnChange(fn: (v: string) => void): void {
    this.onChange = fn;
  }

  registerOnTouched(fn: () => void): void {
    this.onTouched = fn;
  }

  setDisabledState(isDisabled: boolean): void {
    this.disabled = isDisabled;
  }

  onContentChange(val: string): void {
    this.content = val || '';
    this.onChange(this.content);
    this.onTouched();
  }

  private get quill() {
    return this.quillRef?.quillEditor;
  }

  openTablePrompt(): void {
    this.tableRows = 3;
    this.tableCols = 3;
    this.tablePromptOpen = true;
  }

  insertTable(): void {
    const q = this.quill;
    if (!q) return;
    insertHtmlAtCursor(q, insertTableHtml(this.tableRows, this.tableCols));
    this.tablePromptOpen = false;
    this.onContentChange(q.root.innerHTML);
  }

  insertInProcessBlock(): void {
    const q = this.quill;
    if (!q) return;
    insertHtmlAtCursor(q, insertInProcessBlockHtml());
    this.onContentChange(q.root.innerHTML);
  }

  insertHorizontalRule(): void {
    const q = this.quill;
    if (!q) return;
    insertHtmlAtCursor(q, '<hr class="proc-hr" /><p><br></p>');
    this.onContentChange(q.root.innerHTML);
  }

  triggerImageUpload(): void {
    this.imageInput?.nativeElement?.click();
  }

  onImageSelected(ev: Event): void {
    const input = ev.target as HTMLInputElement;
    const file = input.files?.[0];
    if (!file || !file.type.startsWith('image/')) {
      alertify?.warning?.('Please select an image file');
      input.value = '';
      return;
    }
    if (file.size > 2 * 1024 * 1024) {
      alertify?.warning?.('Image must be under 2 MB');
      input.value = '';
      return;
    }
    const reader = new FileReader();
    reader.onload = () => {
      const q = this.quill;
      if (!q) return;
      const range = q.getSelection(true);
      const index = range ? range.index : q.getLength();
      q.insertEmbed(index, 'image', reader.result as string, 'user');
      q.setSelection(index + 1, 0, 'user');
      this.onContentChange(q.root.innerHTML);
      input.value = '';
    };
    reader.readAsDataURL(file);
  }
}
