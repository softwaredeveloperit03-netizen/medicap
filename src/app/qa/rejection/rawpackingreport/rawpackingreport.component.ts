import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-rawpackingreport',
  templateUrl: './rawpackingreport.component.html'
})
export class RawpackingreportComponent implements OnInit {
  isForm = false;
  constructor(private router:Router,private service:DataAccessService) { }

  ngOnInit(): void {
    this.getreport();
  }
  datalist;
  getreport(){
    this.service.get('rejection.php?type=rawpackingrejectionreport').subscribe(response => {
      this.datalist = response;
    })
  }
  closeform(){
    this.router.navigateByUrl('rejection/dashboard')
  }
  saverejection(value){
    if(value.valid){
      alert('Submtted Successfully');
      this.router.navigateByUrl('rejection/dashboard');
    }else{
      alert('All feild Are Required');
    }
  }

}
