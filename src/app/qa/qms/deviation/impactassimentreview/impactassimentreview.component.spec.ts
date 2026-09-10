import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ImpactassimentreviewComponent } from './impactassimentreview.component';

describe('ImpactassimentreviewComponent', () => {
  let component: ImpactassimentreviewComponent;
  let fixture: ComponentFixture<ImpactassimentreviewComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ImpactassimentreviewComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ImpactassimentreviewComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
