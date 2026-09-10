import { ComponentFixture, TestBed } from '@angular/core/testing';

import { HeadreviewComponent } from './headreview.component';

describe('HeadreviewComponent', () => {
  let component: HeadreviewComponent;
  let fixture: ComponentFixture<HeadreviewComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ HeadreviewComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(HeadreviewComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
