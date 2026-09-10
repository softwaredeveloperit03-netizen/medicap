import { ComponentFixture, TestBed } from '@angular/core/testing';

import { GownsDistributionComponent } from './gowns-distribution.component';

describe('GownsDistributionComponent', () => {
  let component: GownsDistributionComponent;
  let fixture: ComponentFixture<GownsDistributionComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ GownsDistributionComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(GownsDistributionComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
