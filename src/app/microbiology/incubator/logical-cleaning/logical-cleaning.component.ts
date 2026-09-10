import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-logical-cleaning',
  templateUrl: './logical-cleaning.component.html',
  styleUrls: ['./logical-cleaning.component.css'],
  providers:[DatePipe]
})
export class LogicalCleaningComponent implements OnInit {
  from_date = '';
  to_date = '';
  today = '';
  results;
  plant_names;
  incubators;
  labours;
 
  constructor(private service: DataAccessService, private datePipe: DatePipe) {
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
  }

  ngOnInit(): void {
    this.changeFilter();
    this.getIncubator();
    this.getLabours();
  }
  getIncubator(){
    this.service.get('equipments.php?type=getBacterialIncubators').subscribe(response =>{
      this.incubators = response;
    });
  }
  getLabours(){
    this.service.get('common.php?type=getOperators').subscribe(response =>{
      this.labours = response;
    });
  }
  changeFilter() {
    this.service.get('microbiology/bactorial.php?type=getPendingBactorial&from_date=' + this.from_date + '&to_date=' + this.to_date).subscribe(response => {
      this.results = response;
    });
  }
  download() {
    this.service.open('microbiology/bactorial.php?type=downloadPendingBactorial&from_date=' + this.from_date + '&to_date=' + this.to_date)
  }
  saveGlasswareRecord(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    this.service.post('microbiology/bactorial.php?type=saveBactorial', JSON.stringify(data.value)).subscribe(response => {
      if (response['status'] === 'success') {
        this.changeFilter();
        alertify.success('Record Inserted successfully');
        data.resetForm();
      } else {
        alertify.error(response['status']);
      }
    });
  }
}
