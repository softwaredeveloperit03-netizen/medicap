import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-intrecive',
  templateUrl: './intrecive.component.html',
  styleUrls: ['./intrecive.component.css']
})
export class IntreciveComponent implements OnInit {

 
  isView = false;
  results;
  employees;
  testing_type = 0;
  selectedResult;
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    // this.getPendingReceiving();
    this.getTestingPersons();
  }
  getData(value){
    if(value=='Production'){
      this.getPendingReceiving_prod();
    }
    else if(value=='Packing'){
      this.getPendingReceiving();
    
    }
  }

  getPendingReceiving(){
    this.service.get('production/technical.php?type=getPendingReceivingForAllocationReciving').subscribe(response => {
      this.results = response;
    });
  }
  getPendingReceiving_prod(){
    this.service.get('production/technical.php?type=getPendingReceivingForAllocationRecivingProd').subscribe(response => {
      this.results = response;
    });
  }

  getTestingPersons() {
    this.service.get('common.php?type=getTestingPersons').subscribe(response => {
      this.employees = response;
    });
  }

  view(index){
    this.selectedResult = this.results[index];
    this.isView = true;
    //this.getTestingPersons();
  }

  submit(data){
    if(!data.valid){
      alertify.error('All Field Required !!!!!!!!');
      return;
    }
    this.service.post('production/technical.php?type=receiveTests&id='+this.selectedResult['id'],JSON.stringify(this.selectedResult)).subscribe(response => {
      if(response['status'] == 'success'){
        alertify.success('Person allocated successfully!');
        this.isView = false;
        // this.getPendingReceiving();
        this.results=[];
      }else{
        alertify.error('Failed an error occured,Please try again!');
      }
    });
  }



}
