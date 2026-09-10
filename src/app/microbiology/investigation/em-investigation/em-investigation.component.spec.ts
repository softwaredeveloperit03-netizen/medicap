import { ComponentFixture, TestBed } from '@angular/core/testing';

import { EmInvestigationComponent } from './em-investigation.component';

describe('EmInvestigationComponent', () => {
  let component: EmInvestigationComponent;
  let fixture: ComponentFixture<EmInvestigationComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ EmInvestigationComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(EmInvestigationComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
