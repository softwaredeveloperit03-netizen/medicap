import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-handover',
  templateUrl: './handover.component.html',
  styleUrls: ['./handover.component.css']
})
export class HandoverComponent implements OnInit {
  selectedResult=[];
  results;
  isView = false;
  constructor(private service: DataAccessService, private router: Router) { }
 


  ngOnInit(): void {
    this.getHandover();
  }
  getHandover(){
    this.service.get('hr/leaveForm.php?type=getHandover').subscribe(response => {
      this.results = response;
    })
  }
  
    view(index) {
      this.selectedResult = this.results[index];
      this.isView = true;
    }

}

