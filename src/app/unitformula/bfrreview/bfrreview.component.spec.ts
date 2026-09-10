import { ComponentFixture, TestBed } from '@angular/core/testing';

import { BfrreviewComponent } from './bfrreview.component';

describe('BfrreviewComponent', () => {
  let component: BfrreviewComponent;
  let fixture: ComponentFixture<BfrreviewComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ BfrreviewComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(BfrreviewComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
