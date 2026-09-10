import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

  complaints;
  isForm = false;

  


  constructor(private service: DataAccessService, private router:Router) {
   }

  ngOnInit() {
    this.getMarketComplaints();
  }

  getMarketComplaints() {
    this.service.get('qaDepartment.php?type=getMarketComplaintslog').subscribe(response => {
      this.complaints = response;
    });
  }
  selectedForm;
  viewForm(index){
    this.selectedForm = this.complaints[index];
    this.isForm = true;
  }
  

  closeForm() {
    this.router.navigateByUrl('/qa/complaints/dashboard');
  }

  downloadView(){
    this.service.open('qaDepartment.php?type=downloadViewMarketComplaints&id='+this.selectedForm['id']);
  }

}
