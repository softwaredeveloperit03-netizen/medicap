import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-leave-card',
  templateUrl: './leave-card.component.html',
  styleUrls: ['./leave-card.component.css']
})
export class LeaveCardComponent implements OnInit {

   results;

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getData();
  }

  getData() {    
    this.service.get('hr/leavepolicy.php?type=getleave_cardempdash').subscribe(response => {
      this.results = response;
    
    });
  }
 
}
