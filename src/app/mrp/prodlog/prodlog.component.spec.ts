import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ProdlogComponent } from './prodlog.component';

describe('ProdlogComponent', () => {
  let component: ProdlogComponent;
  let fixture: ComponentFixture<ProdlogComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ProdlogComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ProdlogComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
