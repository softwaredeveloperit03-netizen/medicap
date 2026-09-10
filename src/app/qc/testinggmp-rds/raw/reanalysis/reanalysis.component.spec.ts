import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ReanalysisComponent } from './reanalysis.component';

describe('ReanalysisComponent', () => {
  let component: ReanalysisComponent;
  let fixture: ComponentFixture<ReanalysisComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ReanalysisComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ReanalysisComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
