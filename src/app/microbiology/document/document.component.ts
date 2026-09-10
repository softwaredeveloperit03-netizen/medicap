  import { Component, OnInit } from '@angular/core';
  import { DataAccessService } from 'src/app/data-access.service';
  import { DatePipe } from '@angular/common';
  import { Router } from '@angular/router';
  declare let alertify; 
@Component({
  selector: 'app-document',
  templateUrl: './document.component.html',
  styleUrls: ['./document.component.css'],
  providers: [DatePipe]
})
export class DocumentComponent implements OnInit {
    isNew = false;
    from_date = '';
    to_date = '';
    today = '';
    results;
    constructor(private service: DataAccessService, private router: Router, private datePipe: DatePipe) {
      this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
      this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
      this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    }
    ngOnInit(): void {
      this.getRequisition();
    }
  
    getRequisition() {
      this.service.get('qa/document.php?type=getDeptRequisitions&from_date=' + this.from_date + '&to_date=' + this.to_date).subscribe(response => {
        this.results = response;
      });
    }
  
    download() {
      this.service.open('qa/document.php?type=downloadDeptRequisitions&from_date=' + this.from_date + '&to_date=' + this.to_date)
    }
  
    saverequisition(data) {
      if (!data.valid) {
        alertify.error('All fields are required');
        return;
      }
      this.service.post('qa/document.php?type=saveRequisition', JSON.stringify(data.value)).subscribe(response => {
        if (response['status'] === 'success') {
          this.getRequisition();
          this.isNew = false;
          alertify.success('Record Inserted successfully');
          data.resetForm();
        } else {
          alertify.error(response['status']);
        }
      });
    }
  }
  