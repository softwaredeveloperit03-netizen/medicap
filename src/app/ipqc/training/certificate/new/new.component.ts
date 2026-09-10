import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  selectedResults=[];
  results;
  isView = false;
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
  }


  view(index) {
    this.selectedResults = this.results[index];
    this.isView = true;
  }
}
