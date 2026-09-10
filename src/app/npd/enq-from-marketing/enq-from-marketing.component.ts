import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-enq-from-marketing',
  templateUrl: './enq-from-marketing.component.html',
  styleUrls: ['./enq-from-marketing.component.css']
})
export class EnqFromMarketingComponent implements OnInit {

  leadResults;
  selectedResult=[]  

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getCompleteLeadForNpd();
  }
 
  getCompleteLeadForNpd() {
    this.service.get('npd/npd.php?type=getCompleteLeadForNpd').subscribe(response => {
      this.leadResults = response;
    });
  }
 
  viewDoc(url) {
    url = this.service.url + '../../upload/leads/' + url;
    window.open(url, '_blank');
  }

  isView = false;

  selectedEnquiry = [];
  view(data){
    this.selectedEnquiry = data;
    this.isView = true;
  }
 
  selectedProduct = [];
  isProductView = false;
  viewProduct(data){
    this.selectedProduct = data;
    this.isProductView = true;
  }



  
  acknowledgeEnquiry() {

    const temp = {};
    temp['id'] = this.selectedEnquiry['id'];

    this.service.post('npd/npd.php?type=acknowledgeEnquiry', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] = 'success') {
        alertify.success('Enquiry Acknowledge Successfully....');
        this.isView = false;
        this.getCompleteLeadForNpd();
      }else {
          alertify.error('Please try Again');
      }
    });

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


