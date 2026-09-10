import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-checklist-approve',
  templateUrl: './checklist-approve.component.html'
})
export class ChecklistApproveComponent implements OnInit {
  
  isView = false;
  results;
  selectedResult = [];
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    this.getChecklists();
  }

  getChecklists() {
    this.service.get('batch-release.php?type=getPendingChecklists').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  update(status) {
    this.service.get('batch-release.php?type=updateChecklist&status=' + status + '&id=' + this.selectedResult['id']).subscribe(response => {
      if (response['status'] == "success") {
        alert('Checklist Updated Successfully');
        this.isView = false;
        this.getChecklists();
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }
  
}