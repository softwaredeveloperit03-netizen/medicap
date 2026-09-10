import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ClosinchecklistComponent } from './closinchecklist.component';

describe('ClosinchecklistComponent', () => {
  let component: ClosinchecklistComponent;
  let fixture: ComponentFixture<ClosinchecklistComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ClosinchecklistComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ClosinchecklistComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
