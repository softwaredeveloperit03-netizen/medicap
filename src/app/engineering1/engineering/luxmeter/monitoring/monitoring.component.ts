import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { DatePipe } from '@angular/common';
declare let alertify;
@Component({
  selector: 'app-monitoring',
  templateUrl: './monitoring.component.html',
  styleUrls: ['./monitoring.component.css'],
  providers: [DatePipe]

})
export class MonitoringComponent implements OnInit {

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
    this.getMonitaring();
  }

  getMonitaring() {
    this.service.get('engineering/lux.php?type=getOperation&from_date=' + this.from_date + '&to_date=' + this.to_date).subscribe(response => {
      this.results = response;
    });
  }

  download() {
    this.service.open('engineering/lux.php?type=downloadOperation&from_date=' + this.from_date + '&to_date=' + this.to_date)
  }
  savePressure(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    this.service.post('engineering/lux.php?type=saveOperation', JSON.stringify(data.value)).subscribe(response => {
      if (response['status'] === 'success') {
        this.getMonitaring();
        alertify.success('Record Inserted successfully');
        data.resetForm();
      } else {
        alertify.error(response['status']);
      }
    });
  }
}