import { ComponentFixture, TestBed } from '@angular/core/testing';

import { IndexmasterComponent } from './indexmaster.component';

describe('IndexmasterComponent', () => {
  let component: IndexmasterComponent;
  let fixture: ComponentFixture<IndexmasterComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ IndexmasterComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(IndexmasterComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
