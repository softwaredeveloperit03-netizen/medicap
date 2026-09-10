import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-dashboard-new',
  templateUrl: './dashboard-new.component.html',
  styleUrls: ['./dashboard-new.component.css']
})
export class DashboardNewComponent implements OnInit {
  software_type ='Pharma ERP';
  constructor() { }

  ngOnInit(): void {
  }

}
