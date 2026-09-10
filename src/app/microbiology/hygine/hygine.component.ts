import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-hygine',
  templateUrl: './hygine.component.html',
  styleUrls: ['./hygine.component.css'],
  providers:[DatePipe]
})
export class HygineComponent implements OnInit {
  results;
  from_date='';
  to_date ='';
  constructor(private service:DataAccessService ,private datePipe:DatePipe) {      
    this.from_date=this.datePipe.transform(Date.now(),'yyyy-MM-01'); 
    this.to_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd');   }

  ngOnInit(): void {
    this.getPersonnelHygine();
  }

  getPersonnelHygine(){
    this.service.get('microbiology/hygine.php?type=getPersonnelHygine&from_date=' +this.from_date +'&to_date=' +this.to_date).subscribe(response=>{
      this.results=response;
    });
  }

  add(data){
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    this.service.post('microbiology/hygine.php?type=saveHygine',JSON.stringify (data.value)).subscribe(response =>{
      if (response['status'] === 'success') {
        this.getPersonnelHygine();
        alertify.success('Record Inserted successfully');
        data.resetForm();
      } else {
        alertify.error(response['status']);
      }
    });
  }


  download(){
    this.service.open('microbiology/hygine.php?type=downloadPersonnelHygine&from_date=' +this.from_date +'&to_date=' +this.to_date);
  }

}
