import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-awaiting',
  templateUrl: './awaiting.component.html',
  styleUrls: ['./awaiting.component.css']
})
export class AwaitingComponent implements OnInit {

  results;
  results1;
  material_name;
  materials;
  selectedResult=[];
  wast;
  wast1=[];

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getGRNLog();
    this.getMaterials();
    this.getChkListData();
    
    
  }

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
    
    //  this.getLogs();
    this.currentPage=1;
    this.pageSize =10;
    
  }
  getMaterials() {
    this.service.get('common.php?type=getRawMaterials').subscribe(response => {
      this.materials = response;
    })
  }
 

  getGRNLog() {
    this.service.get('store/label.php?type=getAwaitingGRNRawLabels').subscribe(response => {
      this.results = response;
     });
  }
  checklist;
  getChkListData() {
    this.service.get('master/checklist.php?type=get_rec_ChkListByTranID&rec_no=').subscribe(response => {
      this.checklist = response;
    });
  }

  printLabel(index){
     this.selectedResult = this.filteredMaterials[index];
    this.service.open('store/raw.php?type=grnLabelsPDF2&id='+this.selectedResult['id']);
  }
  
  printBarcode(index){
     this.selectedResult = this.filteredMaterials[index];
    this.service.open('store/raw.php?type=printBarcode&id='+this.selectedResult['id']);
  }
  

   

  searchQuery;


  get filteredMaterials(): any[] {
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return this.results; // If search query is empty or whitespace, return all materials
    }
    
    const query = this.searchQuery.toLowerCase().trim(); // Convert search query to lowercase and trim whitespace
  
    return this.results.filter(material => {
      // Check if any field of the material contains the search query
      return Object.entries(material).some(([key, value]) => {
        if (key === 'entry_date') {
          // Convert the value to a Date object if it's not already
          const dateValue = typeof value === 'string' ? new Date(value) : value;
          // Check if the date value is valid and includes the search query
          return dateValue instanceof Date && dateValue.toISOString().slice(0, 10).includes(query);
        } else {
          // Convert field value to lowercase and check if it includes the search query
          return value && value.toString().toLowerCase().includes(query);
        }
      });
    });
  }









  clear(){
    this.material_name = '';
    this.results = this.results1;
  }


}
