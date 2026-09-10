import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-merges',
  templateUrl: './merges.component.html',
  styleUrls: ['./merges.component.css']
})
export class MergesComponent implements OnInit {

  
    //----------------------For Pagination---------------------------------//

    currentPage: number = 1;
    pageSize: number = 10; // Default page size
    calculateStartSrNo(): number {
      return (this.currentPage - 1) * 10 ;
    }
    onPageChange(page: number) {
      this.currentPage = page;
      console.log(this.currentPage);
    }
    onPageSizeChange(event: any) {
      this.pageSize = parseInt(event.target.value, 10); // Parse the selected value to an integer
    }
    viewf(){
      this.isView=false;
      //  this.getLogs();
      this.currentPage=1;
      this.pageSize =10;
    }
 

  results: any[] = [];
  selectedResult: any = {};
  order_type: any;
  materials: any[] = [];
  isView = false;
  searchQuery = '';
  loading = false;

  constructor(private service: DataAccessService) { }

  ngOnInit() {
 
    this.getPendingIndends();
 
  }
 
  getPendingIndends() {
    this.service.get('purchase/indent.php?type=getCheckedIndendsForMergeLog').subscribe((response: any) => {
      this.results = response;
      this.assignColors();
    });
  }

 

  rowColors: { [key: string]: string } = {};
  colorList: string[] = [
    '#FFCDD2', '#C8E6C9', '#BBDEFB', '#FFECB3', '#D1C4E9', '#B2DFDB', '#FFF9C4', '#FFCCBC',
    '#F8BBD0', '#DCEDC8', '#B3E5FC', '#FFE0B2', '#E1BEE7', '#B2EBF2', '#FFF9C4', '#FFCCBC',
    '#EF9A9A', '#A5D6A7', '#90CAF9', '#FFE082', '#CE93D8', '#80DEEA', '#FFEB3B', '#FFAB91',
    '#E57373', '#81C784', '#64B5F6', '#FFD54F', '#BA68C8', '#4DD0E1', '#FFEB3B', '#FF7043',
    '#EF5350', '#66BB6A', '#42A5F5', '#FFCA28', '#AB47BC', '#26C6DA', '#FFEB3B', '#FF5722',
    '#F44336', '#4CAF50', '#2196F3', '#FFC107', '#9C27B0', '#00BCD4', '#FFEB3B', '#E64A19',
    '#E53935', '#43A047', '#1E88E5', '#FFB300', '#8E24AA', '#00ACC1', '#FDD835', '#D84315',
    '#D32F2F', '#388E3C', '#1976D2', '#FFA000', '#7B1FA2', '#0097A7', '#FBC02D', '#BF360C',
    '#C62828', '#2E7D32', '#1565C0', '#FF8F00', '#6A1B9A', '#00838F', '#F9A825', '#FF6F00',
    '#B71C1C', '#1B5E20', '#0D47A1', '#FF6F00', '#4A148C', '#006064', '#F57F17', '#E65100',
    '#D50000', '#00C853', '#2962FF', '#FFD600', '#AA00FF', '#00B8D4', '#C6FF00', '#DD2C00',
    '#FF1744', '#00E676', '#2979FF', '#FFC400', '#D500F9', '#00BFA5', '#AEEA00', '#FF3D00',
    '#F50057', '#69F0AE', '#448AFF', '#FFAB00', '#651FFF', '#00E5FF', '#76FF03', '#FF9100',
    '#FF4081', '#B2FF59', '#40C4FF', '#FFD740', '#7C4DFF', '#18FFFF', '#CCFF90', '#FFAB40'
  ];

  assignColors(): void {
    this.filteredMaterials.forEach((_, index: number) => {
      this.rowColors[index] = this.colorList[index % this.colorList.length];
    });
  }

  /** Get row background color by index for template type safety. */
  getRowColor(index: number): string {
    return this.rowColors[index] ?? '';
  }

  get filteredMaterials(): any[] {
    if (!this.results) return [];
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return this.results; 
    }
    const query = this.searchQuery.toLowerCase().trim(); 
    return this.results.filter(material => {
      return Object.entries(material).some(([key, value]) => {
        if (key === 'entry_date') {
          const dateValue = typeof value === 'string' ? new Date(value) : value;
          return dateValue instanceof Date && dateValue.toISOString().slice(0, 10).includes(query);
        } else {
          return value && value.toString().toLowerCase().includes(query);
        }
      });
    });
  }


  
  view(index) {
    this.selectedResult = this.filteredMaterials[index];
     this.isView = true;
  }
   

}