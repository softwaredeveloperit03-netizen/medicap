import { ComponentFixture, TestBed } from '@angular/core/testing';

import { SamplereqdashboardComponent } from './samplereqdashboard.component';

describe('SamplereqdashboardComponent', () => {
  let component: SamplereqdashboardComponent;
  let fixture: ComponentFixture<SamplereqdashboardComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ SamplereqdashboardComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(SamplereqdashboardComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
