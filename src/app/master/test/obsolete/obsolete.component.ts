import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-test-obsolete',
  templateUrl: './obsolete.component.html',
  styleUrls: ['./obsolete.component.css']
})
export class ObsoleteComponent implements OnInit {

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.getTestsLog();
  }

  results;

  getTestsLog() {
    this.service.get('master/test.php?type=getTestsByStatus&status=Absolute').subscribe((response) => {
      this.results = Array.isArray(response) ? response : [];
    });
  }

  selectedResult = [];
  isView = false;

  view(id) {
    const index = this.results.findIndex((t: any) => t.id === id);
    if (index !== -1) {
      this.selectedResult = this.results[index];
    }
    this.isView = true;
  }

  searchQuery;

  get filteredMaterials(): any[] {
    if (!this.results) return [];
    if (!this.searchQuery || this.searchQuery.trim() === '') return this.results;
    const query = this.searchQuery.toLowerCase().trim();
    return this.results.filter((t: any) =>
      Object.entries(t).some(([, value]) =>
        value != null && value.toString().toLowerCase().includes(query)
      )
    );
  }
}
