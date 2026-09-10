import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-checklist-log',
  templateUrl: './checklist-log.component.html',
  styleUrls: ['./checklist-log.component.css']
})
export class ChecklistLogComponent implements OnInit {

  isView = false;
  results;
  selectedResult = [];

  departments;
  sections;

  checkpoints = [];
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getAreaChecklists();
  }

  getAreaChecklists() {
    this.service.get('qa/clearance.php?type=getAreaChecklistslog').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

}
