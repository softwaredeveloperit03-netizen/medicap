import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-form',
  templateUrl: './form.component.html',
  styleUrls: ['./form.component.css'],
  providers: [DatePipe],
})
export class FormComponent implements OnInit {
  date = '';
  today = '';
  time = '';
  remark_by = '';
  done_by = '';

  constructor(private datePipe: DatePipe) {}

  ngOnInit(): void {
    this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd') || '';
    this.date = this.today;
    this.time = this.formatCurrentTime();
  }

  formatCurrentTime(): string {
    return this.datePipe.transform(Date.now(), 'HH:mm') || '';
  }

  saveEntry(form: any) {
    if (!form?.valid) {
      alert('All fields are required');
      return;
    }
    alert('Save is not configured for this form yet.');
  }
}
