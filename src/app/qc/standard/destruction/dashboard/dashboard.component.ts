import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify;


@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {

  isView= false;
  selectedResult=[];
  results;
  standards;
  grades;

  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    this.getRecevingOrders();
    this.getGrades();
   
  }

  getRecevingOrders(){
    this.service.get('qc/standard/order.php?type=getRecevingOrders').subscribe(response => {
      this.results = response;
    })
  }
 
  getGrades(){
    this.service.get('common.php?type=getGrades').subscribe(response => {
      this.grades = response;
    })
  }

  view(index){
    this.selectedResult=this.results[index];
    this.isView=true;
  }

  submit(data){
    if(!data.valid){
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
    this.service.post('qc/standard/order.php?type=saveStorage&id=' + this.selectedResult['id'], JSON.stringify(temp)).subscribe(response =>{
      if(response ['status']== 'success'){
        alertify.success('Data save succeessfully');
        data.resetForm();
          this.router.navigate(['/qc/standard/storage']);
      }else {
        alertify.error('Failed: An error occured, please try again!');
      }
    })
  }

}
