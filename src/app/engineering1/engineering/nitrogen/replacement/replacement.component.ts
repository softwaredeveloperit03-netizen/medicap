import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { DatePipe } from '@angular/common';
declare let alertify;
@Component({
  selector: 'app-replacement',
  templateUrl: './replacement.component.html',
  styleUrls: ['./replacement.component.css'],
  providers: [DatePipe]

})
export class ReplacementComponent implements OnInit {

  from_date = '';
  to_date = '';
  today = '';
  results;
  constructor(private service: DataAccessService, private datePipe: DatePipe) {
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
  }

  ngOnInit(): void {
    this.getReplacementRecords();
  }

  getReplacementRecords() {
    this.service.get('engineering/nitrogen.php?type=getReplacements&from_date=' + this.from_date + '&to_date=' + this.to_date).subscribe(response => {
      this.results = response;
    });
  }

  download() {
    this.service.open('engineering/nitrogen.php?type=downloadReplacements&from_date=' + this.from_date + '&to_date=' + this.to_date)
  }
  saveRecords(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    this.service.post('engineering/nitrogen.php?type=saveReplacement', JSON.stringify(data.value)).subscribe(response => {
      if (response['status'] === 'success') {
        this.getReplacementRecords();
        alertify.success('Record Inserted successfully');
        data.resetForm();
      } else {
        alertify.error(response['status']);
      }
    });
  }
}