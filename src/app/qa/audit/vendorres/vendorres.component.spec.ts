import { ComponentFixture, TestBed } from '@angular/core/testing';

import { VendorresComponent } from './vendorres.component';

describe('VendorresComponent', () => {
  let component: VendorresComponent;
  let fixture: ComponentFixture<VendorresComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ VendorresComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(VendorresComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
