import { Component, OnInit } from '@angular/core';
// import { DatePipe } from '@angular/common';
import { DataAccessService } from 'src/app/data-access.service';
import {Router} from  '@angular/router';
declare let alertify
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  meter_reading; 
  last_reading; 
  difference;
  constructor(private service: DataAccessService,private router : Router) {
  }

  ngOnInit() {
  }
  getDifference(){
      console.log(this.meter_reading)
      console.log(this.last_reading)
      this.difference = (this.meter_reading - this.last_reading);
      console.log(this.difference)
  }
  
  checkValue(event) {
    if (event.target.value < 0) {
      event.target.value = 0;
    }
  }
  saveReading(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    this.service.post('engineering/electricity.php?type=saveElectricity',JSON.stringify(data.value)).subscribe(response => {
      if(response['status'] == 'success') {
       alertify.success('Record Inserted Successfully');
       this.router.navigate(['/engineering/electricity/'])
       data.resetForm();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }
}
