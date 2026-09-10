import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {

  results;

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getReceivingLabels();
  }

  getReceivingLabels() {
    this.service.get('store/label.php?type=getReceivingLabels').subscribe(response => {
      this.results = response;
    });
  }

}
