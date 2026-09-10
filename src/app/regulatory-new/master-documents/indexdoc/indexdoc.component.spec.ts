import { ComponentFixture, TestBed } from '@angular/core/testing';

import { IndexdocComponent } from './indexdoc.component';

describe('IndexdocComponent', () => {
  let component: IndexdocComponent;
  let fixture: ComponentFixture<IndexdocComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ IndexdocComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(IndexdocComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
