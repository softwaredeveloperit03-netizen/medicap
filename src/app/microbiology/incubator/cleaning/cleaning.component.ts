import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-cleaning',
  templateUrl: './cleaning.component.html',
  styleUrls: ['./cleaning.component.css'],
  providers:[DatePipe]
})
export class CleaningComponent implements OnInit {

  from_date = '';
  to_date = '';
  results;
  labours;
  lafs;
  balances;
  incubators;

  constructor(private service : DataAccessService,private datePipe :DatePipe) {
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    }

  ngOnInit(): void {
    this.getAutoclave();
    this.getLabours();
    this.getIncubator();
  }
  getIncubator(){
    this.service.get('equipments.php?type=getBODIncubators').subscribe(response =>{
      this.incubators = response;
    });
  }
  getLabours(){
    this.service.get('common.php?type=getOperators').subscribe(response =>{
      this.labours = response;
    });
  }
 
  getAutoclave(){
    this.service.get('microbiology/incubator.php?type=getPendingIncubator&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response =>{
      this.results = response;
    });
  }
  
  download(){
    this.service.open('microbiology/incubator.php?type=downloadPendingIncubator&from_date='+this.from_date+'&to_date='+this.to_date)
  }
  save(data){
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    this.service.post('microbiology/incubator.php?type=saveIncubator',JSON.stringify (data.value)).subscribe(response =>{
      if (response['status'] === 'success') {
        this.getAutoclave();
        alertify.success('Record Inserted successfully');
        data.resetForm();
      } else {
        alertify.error(response['status']);
      }
    });
  }
  


}
