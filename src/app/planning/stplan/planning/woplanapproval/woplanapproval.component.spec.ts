import { ComponentFixture, TestBed } from '@angular/core/testing';

import { WoplanapprovalComponent } from './woplanapproval.component';

describe('WoplanapprovalComponent', () => {
  let component: WoplanapprovalComponent;
  let fixture: ComponentFixture<WoplanapprovalComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ WoplanapprovalComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(WoplanapprovalComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
