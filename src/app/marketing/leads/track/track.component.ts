import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import {
  getDescriptionLines,
  getSavedEntryProductsLabel,
  parseClientServiceEntries,
  SavedServiceEntry,
} from 'src/app/marketing/clients/client-service.helper';
declare let alertify;

interface LeadEnquiry {
  id?: number | string;
  client_code?: string;
  enquiry_no?: string;
  productList?: any[];
  [key: string]: any;
}

@Component({
  selector: 'app-track',
  templateUrl: './track.component.html',
  styleUrls: ['./track.component.css']
})
export class TrackComponent implements OnInit {
  leadResults: any[] = [];
  selectedResult: any[] = [];

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getInprocessLeads();
  }


  
 
  getInprocessLeads() {
    this.service.get('marketing/lead.php?type=getInprocessLeads').subscribe((response: any) => {
      this.leadResults = Array.isArray(response) ? response : [];
    });
  }
 

  viewDoc(url) {
    url = this.service.url + '../../upload/leads/' + url;
    window.open(url, '_blank');
  }

  isView = false;

  selectedEnquiry: LeadEnquiry = {};
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
 
  selectedProduct: any = {};
  isProductView = false;

  private toDateInputValue(value: any): string {
    if (value == null || value === '') {
      return '';
    }
    if (typeof value === 'string') {
      if (/^\d{4}-\d{2}-\d{2}/.test(value)) {
        return value.slice(0, 10);
      }
      const parsed = new Date(value);
      if (!isNaN(parsed.getTime())) {
        return parsed.toISOString().slice(0, 10);
      }
    }
    return '';
  }

  viewProduct(data: any) {
    this.selectedProduct = { ...(data || {}) };
    this.selectedProduct.tentative_launch_dt = this.toDateInputValue(this.selectedProduct.tentative_launch_dt);
    if (!this.selectedProduct.client_code && this.selectedEnquiry['client_code']) {
      this.selectedProduct.client_code = this.selectedEnquiry['client_code'];
    }
    ['target_price', 'mrp'].forEach((key) => {
      const val = this.selectedProduct[key];
      if (val === '' || val == null) {
        this.selectedProduct[key] = null;
      }
    });
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
 
 
  

 

  submitActions(form) {

    if (form.valid) {
      const temp = form.value;
      temp['enquiry_no'] = this.selectedEnquiry['enquiry_no'];
      temp['id'] = this.selectedEnquiry['id'];

      this.service.post('marketing/lead.php?type=saveAction', JSON.stringify(form.value)).subscribe(response => {
        if (response['status'] == 'success') {
          form.resetForm();
          alertify.success('Action Saved Successfully....');
          this.isView = false;
          this.getInprocessLeads();
        } else {
          alertify.error('Please try Again');
        }
      });

    }else {
      alertify.error('All Field Required !!!!!');
    }
  }


  closedEnquiry(){
    
  }

  makeFinalUpdateProduct() {
    const launchDt = this.toDateInputValue(this.selectedProduct.tentative_launch_dt);
    const productName = (this.selectedProduct.product_name || '').trim();

    if (!launchDt || !productName) {
      alertify.error('Tentative Launch Date and Product Name are required.');
      return;
    }
    if (!this.selectedProduct?.id) {
      alertify.error('Product id missing. Refresh the page and try again.');
      return;
    }

    const payload = {
      ...this.selectedProduct,
      product_name: productName,
      tentative_launch_dt: launchDt,
    };
    if (!payload.client_code && this.selectedEnquiry['client_code']) {
      payload.client_code = this.selectedEnquiry['client_code'];
    }
    if (this.selectedEnquiry['id']) {
      payload.enquiryId = this.selectedEnquiry['id'];
    }

    this.service
      .postTextResponse('marketing/lead.php?type=makeFinalUpdateProduct', JSON.stringify(payload))
      .subscribe({
        next: (raw: string) => {
          let response: any;
          try {
            response = this.service.parsePhpJson(raw);
          } catch {
            alertify.error('Invalid server response. Deploy marketing/lead.php if not done yet.');
            return;
          }
          if (response?.status === 'success') {
            alertify.success('Product marked as FINAL successfully.');
            this.isProductView = false;
            const enquiryId = this.selectedEnquiry['id'];
            this.service.get('marketing/lead.php?type=getInprocessLeads').subscribe((leads: any) => {
              this.leadResults = Array.isArray(leads) ? leads : [];
              const updated = Array.isArray(leads) ? leads.find((l: any) => l.id === enquiryId) : null;
              if (updated) {
                this.selectedEnquiry = { ...updated };
                this.savedServiceEntries = parseClientServiceEntries(this.selectedEnquiry);
                this.isView = true;
              } else {
                this.isView = false;
              }
            });
          } else {
            alertify.error(response?.message || response?.status || 'Please try again');
          }
        },
        error: () => alertify.error('Save failed. Check your connection.'),
      });
  }

  completeEnquiry(status) {

    const hasFinal = this.selectedEnquiry['productList']?.some(
      (product: any) => product.status?.toUpperCase() === 'FINAL'
    );

    if (!hasFinal) {
      alertify.error("At least one product must have status FINAL!");
      return;
    }

    const temp = {};
    temp['id'] = this.selectedEnquiry['id'];
    temp['status'] = status;

    this.service.post('marketing/lead.php?type=completeEnquiry', JSON.stringify(temp)).subscribe({
      next: (response: any) => {
        if (response?.status === 'success') {
          alertify.success('Action Saved Successfully....');
          this.isView = false;
          this.getInprocessLeads();
        } else {
          alertify.error(response?.message || response?.status || 'Please try Again');
        }
      },
      error: () => alertify.error('Save failed. Check your connection.'),
    });

  }

 

}



 