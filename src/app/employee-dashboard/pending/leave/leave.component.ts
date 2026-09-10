import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
@Component({
  selector: 'app-leave',
  templateUrl: './leave.component.html',
  styleUrls: ['./leave.component.css']
})
export class LeaveComponent implements OnInit {
  results;
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
  }
  getLeaveForm() {
    this.service.get('hr/leaveForm.php?type=getLeaveForm').subscribe(response => {
      this.results = response;
    })
  }

}
