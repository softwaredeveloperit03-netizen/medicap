import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import {
  getDescriptionLines,
  getSavedEntryProductsLabel,
  parseClientServiceEntries,
  SavedServiceEntry,
} from 'src/app/marketing/clients/client-service.helper';
declare let alertify;

@Component({
  selector: 'app-firstres',
  templateUrl: './firstres.component.html',
  styleUrls: ['./firstres.component.css']
})
export class FirstresComponent implements OnInit {


  leadResults;
  selectedResult=[]  

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.get_rights();
  }



  dept_head = 'No';
  rights;

  get_rights() {
   this.service.get('hr/employee.php?type=getrights&emp_id=' + localStorage.getItem('emp_id') +'&dep_name=' +localStorage.getItem('department') ).subscribe((response) => {
       this.rights = response;
       this.dept_head = this.rights[0].dept_head;
        this.getLeads(this.dept_head);
    });
  }
 
  getLeads(leadFor) {
    this.service.get('marketing/lead.php?type=getLeadEnquiry&leadFor='+leadFor).subscribe(response => {
      this.leadResults = response;
    });
  }
 
  viewDoc(url) {
    url = this.service.url + '../../upload/leads/' + url;
    window.open(url, '_blank');
  }

  isView = false;

  selectedEnquiry = [];
  savedServiceEntries: SavedServiceEntry[] = [];

  view(data) {
    this.selectedEnquiry = { ...data };
    this.savedServiceEntries = parseClientServiceEntries(this.selectedEnquiry);
    this.isView = true;
  }

  getDescriptionLines(entry: SavedServiceEntry): string[] {
    return getDescriptionLines(entry.descriptions);
  }

  getSavedEntryProductsLabel(entry: SavedServiceEntry): string {
    return getSavedEntryProductsLabel(entry);
  }
 
  selectedProduct = [];
  isProductView = false;
  viewProduct(data){
    this.selectedProduct = data;
    this.isProductView = true;
  }


   searchQuery;

  get filteredMaterials(): any[] {
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return this.leadResults; // If search query is empty or whitespace, return all materials
    }

    const query = this.searchQuery.toLowerCase().trim(); // Convert search query to lowercase and trim whitespace

    return this.leadResults.filter((material) => {
      // Check if any field of the material contains the search query
      return Object.entries(material).some(([key, value]) => {
        if (key === 'entryOn') {
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


