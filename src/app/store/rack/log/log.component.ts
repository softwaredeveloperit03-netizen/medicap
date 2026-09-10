import { ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {
  isView='Lanes';
 

  constructor(private service: DataAccessService,private cdRef: ChangeDetectorRef) { }

  ngOnInit() {
    this.getSections();
  }


  showGrid = true;
  setView(view: string) {
    this.isView = view;

    // Force datagrid to rebuild
    this.showGrid = false; // Destroy current grid
    this.cdRef.detectChanges(); // Wait for DOM to update
    this.showGrid = true; // Recreate grid
    this.results = [];
  }



  sections ;
  getSections() {
    this.service.get('store/location.php?type=getSections').subscribe(response => {
      this.sections = response;
    });
  }

  lanes;
  section_name = '';
  laneNO = '';
  rack_no = '';

  getLaneBySection() {
    this.service.get('store/location.php?type=getLaneBySection&section_name='+this.section_name).subscribe(response => {
      this.lanes = response;
    });
  } 

  racks;
  getRacksByLaneForSearch() {
    this.service.get('store/location.php?type=getRacksByLane&laneNO='+this.laneNO).subscribe(response => {
      this.racks = response;
    });
  }

  results;
  getDataForBarcodePrinting() {
    this.service.get('store/location.php?type=getDataForBarcodePrinting&isView='+this.isView+'&section_name='+this.section_name+'&laneNO='+this.laneNO+'&rack_no='+this.rack_no).subscribe(response => {
      this.results = response;
    });
  }



  printBarcode(data) {

    let trackingId = '';

    if(this.isView == 'Lanes'){
      trackingId = data['laneNO'];
    }
    else if(this.isView == 'Racks'){
      trackingId = data['rack_no'];
    }
    else if(this.isView == 'Locations'){
      trackingId = data['locationNo'];
    }
    else if(this.isView == 'Palettes'){
      trackingId = data['paletteNo'];
    }
 

    this.service.open('purchase/po/barcodeforWmsMAster.php?trackingId='+encodeURIComponent(trackingId) + '&isView='+encodeURIComponent(this.isView) );
  }
  
  generateBatchPreview() {
    this.service.open('purchase/po/print_multiple_barcodes.php?isView='+this.isView+'&section_name='+this.section_name+'&laneNO='+this.laneNO+'&rack_no='+this.rack_no );
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
