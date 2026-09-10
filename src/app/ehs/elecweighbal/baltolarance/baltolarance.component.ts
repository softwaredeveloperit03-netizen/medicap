import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import {Router} from '@angular/router'
declare let alertify;

@Component({
  selector: 'app-baltolarance',
  templateUrl: './baltolarance.component.html',
  styleUrls: ['./baltolarance.component.css']
})
export class BaltolaranceComponent implements OnInit {

  BalanceList;
  isNew = false;
  
  constructor(
    private service: DataAccessService, 
    private router : Router
  ) { }

  ngOnInit(): void {
    this.getBalanceList();
    this.GET_InvolvedPersons();
  }

  Balance;
  getBalanceList(){
    this.service.get('ehs/electronicWeightingBalance/balalancetolarancelimit.php?type=getBalanceList').subscribe(response =>{
        this.Balance =response
      });
  }

  selectedEmp=[];
    getEmpdata(i){

      this.selectedEmp=this.InvolvedPersons[i-1];
    }

   InvolvedPersons: any = [];
  GET_InvolvedPersons() {
    this.service
      .get('common.php?type=AllEmployeeList')
      .subscribe((response) => {
        this.InvolvedPersons = response;
      });
  }

  saveBalTolarance(data){
    let temp = data.value;
    console.log(temp);
    this.service.post('ehs/electronicWeightingBalance/balalancetolarancelimit.php?type=saveBalanceTolarance',JSON.stringify(temp)).subscribe(response => {
      if(response['status'] == 'success'){
        alertify.success('List of Balances in EHS and Tolerance Limit Saved Successfully');
        this.isNew = false;
        this.getBalanceList();
      }
      else{
        alertify.error('Something went wrong!!');
      }
    });
  }


}
