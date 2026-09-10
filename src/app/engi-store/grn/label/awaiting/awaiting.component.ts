import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-awaiting',
  templateUrl: './awaiting.component.html',
  styleUrls: ['./awaiting.component.css']
})
export class AwaitingComponent implements OnInit {


  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getAwaitingGRNRawLabels();
  }
 
 
  results;
  material_type = 'Raw Material';
  getAwaitingGRNRawLabels() {
    this.service.get('store/label.php?type=getAwaitingGRNRawLabels&material_type=' + this.material_type).subscribe(response => {
      this.results = response;
     });
  }
  

  printLabel(data){
    this.service.open('store/raw.php?type=grnLabelsPDF2&id='+data['id']);
  }
  
  printBarcode(data){
    this.service.open('store/raw.php?type=printBarcode&id='+data['id']);
  }
  


  downloadInspectionChecklist(data) {
    this.service.open('store/raw.php?type=downloadInspectionChecklist&challan_no=' +encodeURIComponent(data['challan_no']) 
    + '&grn_no=' + encodeURIComponent(data['grn_no']) +'&material_code=' + encodeURIComponent(data['material_code'])); ;
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
