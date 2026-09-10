import { Component, EventEmitter, Input, Output } from '@angular/core';

@Component({
  selector: 'app-checked-by-panel',
  templateUrl: './checked-by-panel.component.html',
  styleUrls: ['./checked-by-panel.component.css']
})
export class CheckedByPanelComponent {
  @Input() label = 'Checked By';
  @Input() byName = '';
  @Input() byDate = '';
  @Input() canCheck = false;
  @Input() checking = false;
  @Input() checkLabel = 'Check';
  @Output() check = new EventEmitter<void>();

  isDone(): boolean {
    return !!(this.byName || this.byDate);
  }

  onCheck() {
    if (this.canCheck && !this.checking) {
      this.check.emit();
    }
  }
}
