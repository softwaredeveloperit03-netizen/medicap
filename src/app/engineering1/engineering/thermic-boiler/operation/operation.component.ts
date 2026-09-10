 import { Component, OnInit } from '@angular/core';
  import { DataAccessService } from 'src/app/data-access.service';
  import { DatePipe } from '@angular/common';
  declare let alertify;
@Component({
  selector: 'app-operation',
  templateUrl: './operation.component.html',
  styleUrls: ['./operation.component.css'],
  providers: [DatePipe]

})
export class OperationComponent implements OnInit {
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
      this.getThermicLog();
    }
  
    getThermicLog() {
      this.service.get('engineering/thermicboiler.php?type=getThermicOperations&from_date=' + this.from_date + '&to_date=' + this.to_date).subscribe(response => {
        this.results = response;
      });
    }
  
    download() {
      this.service.open('engineering/thermicboiler.php?type=downloadThermicOperations&from_date=' + this.from_date + '&to_date=' + this.to_date)
    }
    saveOperation(data) {
      if (!data.valid) {
        alertify.error('All fields are required');
        return;
      }
      this.service.post('engineering/thermicboiler.php?type=saveThermicOperation', JSON.stringify(data.value)).subscribe(response => {
        if (response['status'] === 'success') {
          this.getThermicLog();
          alertify.success('Record Inserted successfully');
          data.resetForm();
        } else {
          alertify.error(response['status']);
        }
      });
    }
  }