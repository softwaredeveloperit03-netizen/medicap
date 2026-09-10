  import { Component, OnInit } from '@angular/core';
  import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-label',
  templateUrl: './label.component.html',
  styleUrls: ['./label.component.css']
})
export class LabelComponent implements OnInit {

  
    constructor(private service: DataAccessService) { }
  
    ngOnInit() {
      this.getBatchesForBarcodePrintingFOrWMS();
    }
   
   
    results;
    material_type = 'Raw Material';
    getBatchesForBarcodePrintingFOrWMS() {
      this.service.get('store/label.php?type=getBatchesForBarcodePrintingFOrWMS&material_type=' + this.material_type).subscribe(response => {
        this.results = response;
       });
    }
     


    printBarcode(data) {
      this.service.open('purchase/po/print_barcode.php?material_code='+encodeURIComponent(data['material_code'])
      +'&total_containers='+encodeURIComponent(data['total_containers'])
      +'&batch_no='+encodeURIComponent(data['batch_no'])
      +'&material_name='+encodeURIComponent(data['material_name'])
      +'&trackingId='+encodeURIComponent(data['trackingId']));
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
  