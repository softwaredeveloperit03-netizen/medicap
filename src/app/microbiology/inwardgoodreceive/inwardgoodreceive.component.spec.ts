import { ComponentFixture, TestBed } from '@angular/core/testing';

import { InwardgoodreceiveComponent } from './inwardgoodreceive.component';

describe('InwardgoodreceiveComponent', () => {
  let component: InwardgoodreceiveComponent;
  let fixture: ComponentFixture<InwardgoodreceiveComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ InwardgoodreceiveComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(InwardgoodreceiveComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
