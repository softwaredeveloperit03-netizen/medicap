import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { DatePipe } from '@angular/common';
declare let alertify;
@Component({
  selector: 'app-pressure',
  templateUrl: './pressure.component.html',
  styleUrls: ['./pressure.component.css'],
  providers:[DatePipe]
})
export class PressureComponent implements OnInit {
  from_date = '';
  to_date = '';
  today = '';
  results;
  plant_names;
    constructor(private service : DataAccessService,private datePipe :DatePipe) {
      this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
      this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
      this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
     }
  
    ngOnInit(): void {
      this.service.observablePlant.subscribe(response =>{
        this.plant_names = response;
      });
      this.getFilterPressure();
    }
  
    getFilterPressure(){
      this.service.get('engineering/aircompressor.php?type=getFilterPressure&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response =>{
        this.results = response;
      });
    }
   
    download(){
      this.service.open('engineering/aircompressor.php?type=downloadFilterPressure&from_date='+this.from_date+'&to_date='+this.to_date)
    }
    saveOperation(data){
      if (!data.valid) {
        alertify.error('All fields are required');
        return;
      }
      this.service.post('engineering/aircompressor.php?type=saveFilterPressure',JSON.stringify (data.value)).subscribe(response =>{
        if (response['status'] === 'success') {
          this.getFilterPressure();
          alertify.success('Record Inserted successfully');
          data.resetForm();
        } else {
          alertify.error(response['status']);
        }
      });
    }
  }