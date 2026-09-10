import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-storagecondi',
  templateUrl: './storagecondi.component.html',
  styleUrls: ['./storagecondi.component.css']
})
export class StoragecondiComponent implements OnInit {

  storage_conditions: any[] = [];
  loading = false;
  totalRecords = 0;

  constructor(public service: DataAccessService, private router: Router) {}

  ngOnInit(): void {
    this.getStorageConditions();
  }

  getStorageConditions(): void {
    this.loading = true;
    this.service.fetchStorageConditionsList().subscribe({
      next: (list) => {
        this.storage_conditions = Array.isArray(list) ? list : [];
        this.totalRecords = this.storage_conditions.length;
        this.loading = false;
      },
      error: () => {
        this.storage_conditions = [];
        this.totalRecords = 0;
        this.loading = false;
        alertify.error('Unable to load storage conditions');
      },
    });
  }

  saveStorageCondition(data: { valid: boolean; value: any; resetForm: () => void }) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    this.service.post('master/master.php?type=save_storage_condition', JSON.stringify(data.value)).subscribe((response: any) => {
      if (response['status'] == 'success') {
        data.resetForm();
        this.getStorageConditions();
        alertify.success('Saved successfully');
      } else {
        alertify.error(response['status'] || 'Failed to save');
      }
    });
  }
}
