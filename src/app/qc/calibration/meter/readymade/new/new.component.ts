import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { DatePipe } from '@angular/common';
declare let alertify;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css'],
  providers: [DatePipe],
})
export class NewComponent implements OnInit {
  today = '';
  received_date = '';

  constructor(
    private service: DataAccessService,
    private router: Router,
    private datePipe: DatePipe
  ) {
    this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd') || '';
    this.received_date = this.today;
  }

  ngOnInit(): void {}

  savebuffer(form: any) {
    if (!form?.valid) {
      alertify.error('All fields are required');
      return;
    }

    this.service
      .postTextResponse(
        'qc/calibration/meter.php?type=saveReadymadeBuffer',
        JSON.stringify(form.value)
      )
      .subscribe((text) => {
        const response = this.service.parsePhpJson(text);
        if (response['status'] === 'success') {
          alertify.success('Saved Successfully');
          form.resetForm();
          this.received_date = this.today;
          this.router.navigate(['/qc/calibration/meter/readymade/buffer']);
        } else {
          alertify.error(response['message'] || 'Failed to save, please try again');
        }
      });
  }
}
