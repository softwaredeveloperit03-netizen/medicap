import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify; 

@Component({
  selector: 'app-balance',
  templateUrl: './balance.component.html',
  styleUrls: ['./balance.component.css'],
  providers:[DatePipe]
})
export class BalanceComponent implements OnInit {
  loading;
  checkOther;
  from_date = '';
  to_date = '';
  today = '';
  results;
  isNew=false;
  isView = false;
  selectedResult=[];
  constructor(private service : DataAccessService,private datePipe :DatePipe) {
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
  }

  addEmployees(){

    
  }
  ngOnInit(): void {
    this.getHdpe();
  }
  

  getHdpe(){
    this.service.get('microbiology/hdpe.php?type=getRecords&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response =>{
      this.results = response;
    });
  }
  download(){
    this.service.open('microbiology/hdpe.php?type=downloadRecords&from_date='+this.from_date+'&to_date='+this.to_date)
  }

  save(data){
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value; 
    this.service.post('microbiology/hdpe.php?type=saveRecord',JSON.stringify (temp)).subscribe(response =>{
      if (response['status'] === 'success') {
        this.getHdpe();
        alertify.success('Record Inserted successfully');
        this.isNew=false;
        data.resetForm();
      } else {
        alertify.error(response['status']);
      }
    });
  }


}

