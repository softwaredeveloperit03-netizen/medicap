import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-fesbility-form',
  templateUrl: './fesbility-form.component.html',
  styleUrls: ['./fesbility-form.component.css']
})
export class FesbilityFormComponent implements OnInit {

  leadResults;
  selectedResult=[]  

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getEnquiryProductWiseForFeasibility();
  }
 
  getEnquiryProductWiseForFeasibility() {
    this.service.get('npd/npd.php?type=getEnquiryProductWiseForFeasibility').subscribe(response => {
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
    this.packType = "**Primary Packing :- " + this.selectedEnquiry['primary_packaging_type'] +
     ' ** Secondary Packing :- '+this.selectedEnquiry['secondary_packaging_type'];
    this.labelClaim = "**Primary Claim :- " + this.selectedEnquiry['primary_claims'] +
     " ** Secondary Claim :- "+this.selectedEnquiry['secondary_claim'];
  }
 
  isProductView = false;
  labelClaim = "";
  packType = "";
  viewProduct(){
    this.isProductView = !this.isProductView;

  }


  ingredients = [];
  addIngredient(data){
    if(!data.valid){
      alertify.error("All Field Required!!!");
      return;
    }
    this.ingredients.push(data.value);
    data.reset();
  }

  delIngredient(i){
    this.ingredients.splice(i,1);
  }

  remark = '';
  totalBulkCost = '';
  prodFeasibiTcd = '';

  saveFeasibilityForm() {

    if(this.ingredients?.length == 0){ alertify.error("Please Add Ingredients!!!!!"); return;}
    if(this.labelClaim == ''){alertify.error("Please Add Label Claim!!!!!");return;}
    if(this.totalBulkCost == ''){ alertify.error("Please Add Total Bulk Cost!!!!!"); return;}
    if(this.packType == ''){ alertify.error("Please Add Pack Type!!!!!"); return;}
    if(this.packType == ''){alertify.error("Please Add Product Feasibility & TCD!!!!!"); return;}
 
    const temp = {};
    temp['id'] = this.selectedEnquiry['id'];
    temp['ingredients'] = this.ingredients;
    temp['remark'] = this.remark;
    temp['labelClaim'] = this.labelClaim;
    temp['totalBulkCost'] = this.totalBulkCost;
    temp['packType'] = this.packType;
    temp['prodFeasibiTcd'] = this.prodFeasibiTcd;

    this.service.post('npd/npd.php?type=saveFeasibilityForm', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] = 'success') {
        alertify.success('Enquiry Acknowledge Successfully....');
        this.isView = false;
        this.getEnquiryProductWiseForFeasibility();
        this.remark = '';
        this.labelClaim = '';
        this.totalBulkCost = '';
        this.packType = '';
        this.prodFeasibiTcd = '';
        this.ingredients = [];

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


