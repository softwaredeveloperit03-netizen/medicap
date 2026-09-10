import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ReqCollectionComponent } from './req-collection.component';

describe('ReqCollectionComponent', () => {
  let component: ReqCollectionComponent;
  let fixture: ComponentFixture<ReqCollectionComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ReqCollectionComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ReqCollectionComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
