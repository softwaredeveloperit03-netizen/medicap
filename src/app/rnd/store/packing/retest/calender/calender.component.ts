import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-calender',
  templateUrl: './calender.component.html',
  styleUrls: ['./calender.component.css']
})
export class CalenderComponent implements OnInit {
  results;
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.service.get('store/raw.php?type=getRetestCalendar').subscribe(response => {
      this.results = response;
    });
  }

}
