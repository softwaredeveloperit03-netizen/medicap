import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-calender',
  templateUrl: './calender.component.html',
  styleUrls: ['./calender.component.css']
})
export class CalenderComponent implements OnInit {

  results;
  selectedStability = [];
  isView = false;
  constructor(public service: DataAccessService) { }

  ngOnInit(): void {
    this.getStabilities();
  }

  getStabilities() {
    this.service.get('stability.php?type=getStabilities').subscribe(response => {
      this.results = response;
    });
  }

  viewProtocol(index) {
    this.selectedStability = this.results[index];
    this.isView = true;
  }

}
