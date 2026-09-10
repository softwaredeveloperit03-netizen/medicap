import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { DatePipe } from '@angular/common';
declare let alertify;
@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
  providers: [DatePipe]

})
export class DashboardComponent implements OnInit {
  
    from_date = '';
    to_date = '';
    today = '';
    results;
    plant_names;
    constructor(private service: DataAccessService, private datePipe: DatePipe) {
      this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
      this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
      this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    }
  
    ngOnInit(): void {
      this.service.observablePlant.subscribe(response => {
        this.plant_names = response;
      });
      this.getCalculator();
    }
  
    getCalculator() {
      this.service.get('qa/calculator.php?type=getCalculator&from_date=' + this.from_date + '&to_date=' + this.to_date).subscribe(response => {
        this.results = response;
      });
    }
  
    download() {
      this.service.open('qa/calculator.php?type=downloadCalculator&from_date=' + this.from_date + '&to_date=' + this.to_date)
    }
    saveCalculator(data) {
      if (!data.valid) {
        alertify.error('All fields are required');
        return;
      }
      this.service.post('qa/calculator.php?type=saveCalculator', JSON.stringify(data.value)).subscribe(response => {
        if (response['status'] === 'success') {
          this.getCalculator();
          alertify.success('Record Inserted successfully');
          data.resetForm();
        } else {
          alertify.error(response['status']);
        }
      });
    }
  }