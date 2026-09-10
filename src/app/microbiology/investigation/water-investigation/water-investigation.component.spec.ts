import { ComponentFixture, TestBed } from '@angular/core/testing';

import { WaterInvestigationComponent } from './water-investigation.component';

describe('WaterInvestigationComponent', () => {
  let component: WaterInvestigationComponent;
  let fixture: ComponentFixture<WaterInvestigationComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ WaterInvestigationComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(WaterInvestigationComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
