import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-return',
  templateUrl: './return.component.html'
})
export class ReturnComponent implements OnInit {

  isForm = false;
  constructor(private router:Router, private service:DataAccessService) { }

  ngOnInit(): void {
    this.getreturn();
  }
  closeform(){
    // this.router.navigateByUrl('rejection/dashboard')
    this.isForm = false;
  }
  datalist = [];
  getreturn(){
    this.service.get('rejection.php?type=getreturn').subscribe((response:any) => {
      this.datalist = response;
    })
  }
  saverejection(value){
    if(value.valid){
      alert('Submtted Successfully');
      this.router.navigateByUrl('rejection/dashboard');
    }else{
      alert('All feild Are Required');
    }
  }
  selecteddata;
  viewreport(index){
    this.selecteddata = this.datalist[index];
    this.isForm = true;
  }

}
