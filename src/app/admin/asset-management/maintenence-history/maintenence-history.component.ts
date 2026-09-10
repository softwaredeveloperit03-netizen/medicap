import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-maintenence-history',
  templateUrl: './maintenence-history.component.html',
  styleUrls: ['./maintenence-history.component.css']
})
export class MaintenenceHistoryComponent implements OnInit {

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.getDepartmentsWithSections();
    this.getAssetRecord();
  }

  departments;
  getDepartmentsWithSections() {
    this.service
      .get('admin/asset.php?type=getDepartmentsWithSections')
      .subscribe((response: any) => {
        this.departments = response;
      });
  }

  results;
  dept = 'ALL';
  getAssetRecord() {
    this.service
      .get('admin/asset.php?type=getMaintenanceAssetRecord&dept=' + this.dept)
      .subscribe((response: any) => {
        this.results = response;
      });
  }

  searchQuery;

  get filteredMaterials(): any[] {
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return this.results; // If search query is empty or whitespace, return all materials
    }

    const query = this.searchQuery.toLowerCase().trim(); // Convert search query to lowercase and trim whitespace

    return this.results.filter((material) => {
      // Check if any field of the material contains the search query
      return Object.entries(material).some(([key, value]) => {
        if (key === 'entry_date') {
          // Convert the value to a Date object if it's not already
          const dateValue = typeof value === 'string' ? new Date(value) : value;
          // Check if the date value is valid and includes the search query
          return (
            dateValue instanceof Date &&
            dateValue.toISOString().slice(0, 10).includes(query)
          );
        } else {
          // Convert field value to lowercase and check if it includes the search query
          return value && value.toString().toLowerCase().includes(query);
        }
      });
    });
  }
}
